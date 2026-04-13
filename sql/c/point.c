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
    curl_easy_setopt(curl, CURLOPT_TIMEOUT, 10L);
    curl_easy_setopt(curl, CURLOPT_CONNECTTIMEOUT, 5L);
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

/* Find key:"value" within a bounded region of JSON and copy value into dest. */
static void parse_field(const char *json, const char *end,
                        const char *key, char *dest, size_t dest_size) {
    char search[256];
    snprintf(search, sizeof(search), "\"%s\":\"", key);
    const char *p = strstr(json, search);
    if (!p || (end && p >= end)) { dest[0] = '\0'; return; }
    p += strlen(search);
    parse_json_string(p, dest, dest_size);
}

static int try_parse_parts(const char *body, AddressParts *parts) {
    const char *addr_start = strstr(body, "\"address\":{");
    if (!addr_start) return 0;

    addr_start += strlen("\"address\":{");
    const char *addr_end = strchr(addr_start, '}');

    parse_field(addr_start, addr_end, "house_number", parts->house_number, sizeof(parts->house_number));
    parse_field(addr_start, addr_end, "road",         parts->road,         sizeof(parts->road));
    parse_field(addr_start, addr_end, "state",        parts->state,        sizeof(parts->state));
    parse_field(addr_start, addr_end, "postcode",     parts->postcode,     sizeof(parts->postcode));
    parse_field(addr_start, addr_end, "country",      parts->country,      sizeof(parts->country));
    parse_field(addr_start, addr_end, "country_code", parts->country_code, sizeof(parts->country_code));

    const char *city_keys[] = {"city", "town", "village", "suburb", NULL};
    for (int i = 0; city_keys[i]; i++) {
        parse_field(addr_start, addr_end, city_keys[i], parts->city, sizeof(parts->city));
        if (parts->city[0]) break;
    }

    return parts->postcode[0] || parts->city[0] || parts->road[0];
}

AddressParts get_address_parts(char *address) {
    AddressParts parts = {0};
    CURL *curl = curl_easy_init();
    if (!curl) return parts;

    char buf[512];
    strncpy(buf, address, sizeof(buf) - 1);
    buf[sizeof(buf) - 1] = '\0';
    char *query = buf;

    while (*query) {
        while (*query == ' ') query++;

        char *encoded = curl_easy_escape(curl, query, 0);
        char url[1024];
        snprintf(url, sizeof(url),
                 "https://nominatim.openstreetmap.org/search?q=%s&format=json&addressdetails=1&limit=1",
                 encoded);
        curl_free(encoded);

        char *body = fetch_url(url);
        if (body) {
            int found = try_parse_parts(body, &parts);
            free(body);
            if (found) break;
        }

        char *comma = strchr(query, ',');
        if (!comma) break;
        char *next = comma + 1;
        while (*next == ' ') next++;
        if (!strchr(next, ',')) break;
        query = next;
    }

    curl_easy_cleanup(curl);
    return parts;
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

/*
 * nearest_point: find the closest real address to the input by progressively
 * stripping leading comma-separated tokens until Nominatim returns a result.
 *
 * e.g. "7511 Main Blvd, Los Angeles, MO 34908"
 *   1. try "7511 Main Blvd, Los Angeles, MO 34908"  -> []
 *   2. try "Los Angeles, MO 34908"                  -> []
 *   3. try "MO 34908"                               -> match
 */
Point find_nearest(char *address) {
    Point p = {0, 0, ""};
    CURL *curl = curl_easy_init();
    if (!curl) return p;

    char buf[512];
    strncpy(buf, address, sizeof(buf) - 1);
    buf[sizeof(buf) - 1] = '\0';

    char *query = buf;
    while (*query) {
        /* trim leading spaces */
        while (*query == ' ') query++;

        char *encoded = curl_easy_escape(curl, query, 0);
        char url[1024];
        snprintf(url, sizeof(url),
                 "https://nominatim.openstreetmap.org/search?q=%s&format=json&addressdetails=1&limit=1",
                 encoded);
        curl_free(encoded);

        char *body = fetch_url(url);
        if (body) {
            fprintf(stderr, "  trying: %s -> %s\n", query, body);
            const char *cursor = body;
            int found = parse_result(&cursor, &p);
            free(body);
            if (found) break;
        }

        /* strip the first comma-delimited token and retry,
           but stop if the remaining query has no comma —
           a single bare token (e.g. "MS" or "47691") is too ambiguous */
        char *comma = strchr(query, ',');
        if (!comma) break;
        char *next = comma + 1;
        while (*next == ' ') next++;
        if (!strchr(next, ',')) break; /* would leave only one token — stop */
        query = next;
    }

    curl_easy_cleanup(curl);
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
