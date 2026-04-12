#include <mysql/mysql.h>
#include <stdbool.h>
#include <stdlib.h>
#include <string.h>
#include "point.h"

bool addr_to_point_init(UDF_INIT *initid, UDF_ARGS *args, char *message) {
    if (args->arg_count != 1) {
        strcpy(message, "addr_to_point requires 1 string argument");
        return 1;
    }

    if (args->arg_type[0] != STRING_RESULT) {
        strcpy(message, "argument must be a string");
        return 1;
    }

    initid->maybe_null = 1;
    initid->max_length = 25; // 4-byte SRID + 21-byte WKB POINT
    initid->ptr = NULL;

    return 0;
}

char *addr_to_point(UDF_INIT *initid, UDF_ARGS *args,
                    char *result, unsigned long *length,
                    char *is_null, char *error) {
    if (args->args[0] == NULL) {
        *is_null = 1;
        return NULL;
    }

    Point p = get_point(args->args[0]);

    if (p.lat == 0 && p.lon == 0) {
        *is_null = 1;
        return NULL;
    }

    unsigned char *wkb = (unsigned char *)malloc(25);
    if (!wkb) {
        *error = 1;
        return NULL;
    }

    // MySQL geometry = 4-byte SRID (little-endian) + WKB POINT
    unsigned int srid = 4326;
    memcpy(wkb,      &srid,  4); // SRID 4326 (WGS84)
    wkb[4] = 1;                  // byte order: little-endian
    unsigned int type = 1;       // WKB type: Point
    memcpy(wkb + 5,  &type,  4);
    memcpy(wkb + 9,  &p.lon, 8); // X = longitude
    memcpy(wkb + 17, &p.lat, 8); // Y = latitude

    *length = 25;
    initid->ptr = (char *)wkb;
    return initid->ptr;
}

void addr_to_point_deinit(UDF_INIT *initid) {
    free(initid->ptr);
}

bool is_valid_address_init(UDF_INIT *initid, UDF_ARGS *args, char *message) {
    if (args->arg_count != 1) {
        strcpy(message, "is_valid_address requires 1 string argument");
        return 1;
    }
    if (args->arg_type[0] != STRING_RESULT) {
        strcpy(message, "argument must be a string");
        return 1;
    }
    initid->maybe_null = 0;
    return 0;
}

long long is_valid_address(UDF_INIT *initid, UDF_ARGS *args,
                           char *is_null, char *error) {
    if (args->args[0] == NULL) return 0;

    Point p = get_point(args->args[0]);
    return (p.lat != 0 || p.lon != 0) ? 1 : 0;
}

void is_valid_address_deinit(UDF_INIT *initid) {}

bool near_address_init(UDF_INIT *initid, UDF_ARGS *args, char *message) {
    if (args->arg_count != 1) {
        strcpy(message, "near_address requires 1 string argument");
        return 1;
    }
    if (args->arg_type[0] != STRING_RESULT) {
        strcpy(message, "argument must be a string");
        return 1;
    }
    initid->maybe_null = 1;
    initid->max_length = 512;
    initid->ptr = NULL;
    return 0;
}

char *near_address(UDF_INIT *initid, UDF_ARGS *args,
                   char *result, unsigned long *length,
                   char *is_null, char *error) {
    if (args->args[0] == NULL) {
        *is_null = 1;
        return NULL;
    }

    SearchResults sr = quick_search(args->args[0]);

    if (sr.count == 0 || sr.results[0].display_name[0] == '\0') {
        *is_null = 1;
        return NULL;
    }

    size_t len = strlen(sr.results[0].display_name);
    char *buf = malloc(len + 1);
    if (!buf) {
        *error = 1;
        return NULL;
    }

    memcpy(buf, sr.results[0].display_name, len + 1);
    *length = len;
    initid->ptr = buf;
    return initid->ptr;
}

void near_address_deinit(UDF_INIT *initid) {
    free(initid->ptr);
}