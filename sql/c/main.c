//
// Created by Malcolm Stone on 4/10/26.
//
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include "point.h"

static void load_dotenv(const char *path) {
    FILE *f = fopen(path, "r");
    if (!f) return;

    char line[256];
    while (fgets(line, sizeof(line), f)) {
        // strip trailing newline
        line[strcspn(line, "\r\n")] = '\0';

        // skip empty lines and comments
        if (line[0] == '\0' || line[0] == '#') continue;

        char *eq = strchr(line, '=');
        if (!eq) continue;

        *eq = '\0';
        char *key = line;
        char *val = eq + 1;

        // don't overwrite variables already set in the environment
        setenv(key, val, 0);
    }

    fclose(f);
}

int main() {
    load_dotenv(".env");

    char* address = "Mountain View,CA,US";

    Point point = get_point(address);
    printf("Latitude: %.6f, Longitude: %.6f\n", point.lat, point.lon);

    return 0;
}