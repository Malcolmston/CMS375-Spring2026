#ifndef POINT_H
#define POINT_H

typedef struct {
    double lat;
    double lon;
    char display_name[512];
} Point;

Point get_point(char *address);

#endif
