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

Point get_point(char *address) {
    Point p = {0, 0};
    CURL *curl = curl_easy_init();

    if (curl) {
        char *encoded = curl_easy_escape(curl, address, 0);
        char url[1024];
        snprintf(url, sizeof(url),
                 "https://nominatim.openstreetmap.org/search?q=%s&format=json&limit=1",
                 encoded);
        curl_free(encoded);

        struct ResponseBuffer response = {0};
        response.data = malloc(1);
        response.size = 0;

        curl_easy_setopt(curl, CURLOPT_URL, url);
        curl_easy_setopt(curl, CURLOPT_USERAGENT, "CMS375/1.0");
        curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, write_callback);
        curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);

        CURLcode res = curl_easy_perform(curl);

        if (res != CURLE_OK) {
            fprintf(stderr, "Request failed: %s\n", curl_easy_strerror(res));
        } else if (response.data) {
            /* Nominatim returns lat/lon as quoted strings: "lat":"37.42..." */
            char *lat_str = strstr(response.data, "\"lat\":\"");
            char *lon_str = strstr(response.data, "\"lon\":\"");
            if (lat_str && lon_str) {
                lat_str += strlen("\"lat\":\"");
                lon_str += strlen("\"lon\":\"");
                p.lat = atof(lat_str);
                p.lon = atof(lon_str);
            } else {
                fprintf(stderr, "Could not find lat/lon in response\n");
            }
        }

        free(response.data);
        curl_easy_cleanup(curl);
    }

    return p;
}
