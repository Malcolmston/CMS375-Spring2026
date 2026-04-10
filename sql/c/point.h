#ifndef POINT_H
#define POINT_H

typedef struct {
    double lat;
    double lon;
} Point;

Point get_point(char *address);

#endif
