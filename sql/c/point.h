#ifndef POINT_H
#define POINT_H

#define QUICK_SEARCH_LIMIT 5

typedef struct {
    double lat;
    double lon;
    char display_name[512];
} Point;

typedef struct {
    Point results[QUICK_SEARCH_LIMIT];
    int count;
} SearchResults;

Point         get_point(char *address);
SearchResults quick_search(char *address);

#endif
