#!/bin/bash

echo "running erindringer.py";
/usr/local/bin/python3 /usr/src/app/erindringer.py > /proc/1/fd/1;
echo "running efterretninger.py";
/usr/local/bin/python3 /usr/src/app/efterretninger.py > /proc/1/fd/1;
echo "running schools.py";
/usr/local/bin/python3 /usr/src/app/schools.py > /proc/1/fd/1;
echo "running burials.py";
/usr/local/bin/python3 /usr/src/app/burials.py > /proc/1/fd/1;
echo "running police.py";
/usr/local/bin/python3 /usr/src/app/police.py > /proc/1/fd/1;