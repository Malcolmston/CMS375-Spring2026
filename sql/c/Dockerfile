FROM ubuntu:22.04

RUN apt-get update -qq && \
    apt-get install -y -qq cmake gcc libcurl4-openssl-dev default-libmysqlclient-dev && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /src
COPY CMakeLists.txt point.h point.c udf.c main.c ./

RUN cmake . -B /build -DCMAKE_BUILD_TYPE=Release && \
    cmake --build /build --target addr_to_point

CMD ["cp", "/build/addr_to_point.so", "/mysql-plugins/addr_to_point.so"]
