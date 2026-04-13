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

typedef struct {
    char house_number[32];
    char road[256];
    char city[128];
    char state[128];
    char postcode[16];
    char country[128];
    char country_code[8];
} AddressParts;

Point         get_point(char *address);
Point         find_nearest(char *address);
SearchResults quick_search(char *address);
AddressParts  get_address_parts(char *address);

#endif
