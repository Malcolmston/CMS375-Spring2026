cmake_minimum_required(VERSION 3.20)
project(geocode C)

set(CMAKE_C_STANDARD 11)

find_package(CURL REQUIRED)

# Locate MySQL via mysql_config
execute_process(COMMAND mysql_config --libs OUTPUT_VARIABLE MYSQL_LIBS OUTPUT_STRIP_TRAILING_WHITESPACE)
execute_process(COMMAND mysql_config --variable=pkgincludedir OUTPUT_VARIABLE MYSQL_INCLUDE_INNER OUTPUT_STRIP_TRAILING_WHITESPACE)
execute_process(COMMAND mysql_config --variable=pkglibdir OUTPUT_VARIABLE MYSQL_LIB_DIR OUTPUT_STRIP_TRAILING_WHITESPACE)

# mysql_config --variable=pkgincludedir returns .../include/mysql;
# we need the parent (.../include) so <mysql/mysql.h> resolves correctly.
get_filename_component(MYSQL_INCLUDE_DIR "${MYSQL_INCLUDE_INNER}" DIRECTORY)

separate_arguments(MYSQL_LIBS_LIST UNIX_COMMAND "${MYSQL_LIBS}")

# Standalone test binary
add_executable(geocode main.c point.c)
target_link_libraries(geocode PRIVATE CURL::libcurl)

# MySQL UDF shared library
add_library(addr_to_point MODULE point.c udf.c)
target_include_directories(addr_to_point PRIVATE ${MYSQL_INCLUDE_DIR})
target_link_libraries(addr_to_point PRIVATE CURL::libcurl ${MYSQL_LIBS_LIST})
set_target_properties(addr_to_point PROPERTIES
    PREFIX ""
    BUILD_RPATH "${MYSQL_LIB_DIR}"
)
