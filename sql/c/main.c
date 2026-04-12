//
// Created by Malcolm Stone on 4/10/26.
//
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include "point.h"

int main() {
    char* address = "1600 Amphitheatre Parkway, Mountain View, CA";

    Point point = get_point(address);
    printf("Latitude: %.6f, Longitude: %.6f\n", point.lat, point.lon);

    return 0;
}