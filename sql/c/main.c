//
// Created by Malcolm Stone on 4/10/26.
//
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include "point.h"

int main() {
    char* address = "5456 Cedar Avenue, Aurora, MS 47691";

    printf("Searching for: %s\n\n", address);

    SearchResults sr = quick_search(address);

    if (sr.count == 0) {
        printf("No exact results found. Trying nearest match...\n\n");
        Point p = find_nearest(address);
        if (p.display_name[0]) {
            printf("Nearest: %s\n    Lat: %.6f  Lon: %.6f\n", p.display_name, p.lat, p.lon);
        } else {
            printf("No match found.\n");
        }
    } else {
        for (int i = 0; i < sr.count; i++) {
            Point *p = &sr.results[i];
            printf("[%d] %s\n    Lat: %.6f  Lon: %.6f\n\n",
                   i + 1, p->display_name[0] ? p->display_name : "(unknown)", p->lat, p->lon);
        }
    }

    return 0;
}