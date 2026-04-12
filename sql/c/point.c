#include <curl/curl.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>


#include "point.h"

struct ResponseBuffer {
    char *data;
    size_t size;
};

static size_t write_callback(void *contents, size_t size, size_t nmemb, void *userp) {
    size_t realsize = size * nmemb;
    struct ResponseBuffer *buf = (struct ResponseBuffer *)userp;
    char *ptr = realloc(buf->data, buf->size + realsize + 1);
    if (!ptr) return 0;
    buf->data = ptr;
    memcpy(&(buf->data[buf->size]), contents, realsize);
    buf->size += realsize;
    buf->data[buf->size] = '\0';
    return realsize;
}

/* Copy a JSON quoted string value into dest, handling \" escapes. */
static void parse_json_string(const char *src, char *dest, size_t dest_size) {
    size_t i = 0;
    while (*src && i < dest_size - 1) {
        if (src[0] == '\\' && src[1] == '"') {
            dest[i++] = '"';
            src += 2;
        } else if (*src == '"') {
            break;
        } else {
            dest[i++] = *src++;
        }
    }
    dest[i] = '\0';
}

/* Fetch a URL and return a heap-allocated response string (caller must free). */
static char *fetch_url(const char *url) {
    CURL *curl = curl_easy_init();
    if (!curl) return NULL;

    struct ResponseBuffer response = {0};
    response.data = malloc(1);
    response.size = 0;

    curl_easy_setopt(curl, CURLOPT_URL, url);
    curl_easy_setopt(curl, CURLOPT_USERAGENT, "CMS375/1.0");
    curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, write_callback);
    curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);

    CURLcode res = curl_easy_perform(curl);
    curl_easy_cleanup(curl);

    if (res != CURLE_OK) {
        fprintf(stderr, "Request failed: %s\n", curl_easy_strerror(res));
        free(response.data);
        return NULL;
    }
    return response.data;
}

/* Parse one Point from a JSON object starting at cursor; advance cursor past it. */
static int parse_result(const char **cursor, Point *p) {
    const char *pos = *cursor;

    const char *lat_tag = strstr(pos, "\"lat\":\"");
    const char *lon_tag = strstr(pos, "\"lon\":\"");
    const char *dn_tag  = strstr(pos, "\"display_name\":\"");

    if (!lat_tag || !lon_tag) return 0;

    /* Find the closing } of this result so we don't bleed into the next. */
    const char *obj_end = strchr(pos, '}');
    if (obj_end && (lat_tag > obj_end || lon_tag > obj_end)) return 0;

    lat_tag += strlen("\"lat\":\"");
    lon_tag += strlen("\"lon\":\"");
    p->lat = atof(lat_tag);
    p->lon = atof(lon_tag);

    if (dn_tag && (!obj_end || dn_tag < obj_end)) {
        dn_tag += strlen("\"display_name\":\"");
        parse_json_string(dn_tag, p->display_name, sizeof(p->display_name));
    } else {
        p->display_name[0] = '\0';
    }

    *cursor = obj_end ? obj_end + 1 : pos + 1;
    return 1;
}

Point get_point(char *address) {
    Point p = {0, 0, ""};
    CURL *curl = curl_easy_init();
    if (!curl) return p;

    char *encoded = curl_easy_escape(curl, address, 0);
    char url[1024];
    snprintf(url, sizeof(url),
             "https://nominatim.openstreetmap.org/search?q=%s&format=json&addressdetails=1&limit=1",
             encoded);
    curl_free(encoded);
    curl_easy_cleanup(curl);

    char *body = fetch_url(url);
    if (body) {
        const char *cursor = body;
        if (!parse_result(&cursor, &p))
            fprintf(stderr, "No results for: %s\n", address);
        free(body);
    }
    return p;
}

SearchResults quick_search(char *address) {
    SearchResults sr = {0};
    CURL *curl = curl_easy_init();
    if (!curl) return sr;

    char *encoded = curl_easy_escape(curl, address, 0);
    char url[1024];
    snprintf(url, sizeof(url),
             "https://nominatim.openstreetmap.org/search?q=%s&format=json&addressdetails=1&limit=%d",
             encoded, QUICK_SEARCH_LIMIT);
    curl_free(encoded);
    curl_easy_cleanup(curl);

    char *body = fetch_url(url);
    if (body) {
        const char *cursor = body;
        while (sr.count < QUICK_SEARCH_LIMIT) {
            Point p = {0, 0, ""};
            if (!parse_result(&cursor, &p)) break;
            sr.results[sr.count++] = p;
        }
        free(body);
    }
    return sr;
}
