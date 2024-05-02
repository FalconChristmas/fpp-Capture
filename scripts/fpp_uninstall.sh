#!/bin/bash

# fpp-Capture uninstall script
echo "Running fpp-Capture uninstall Script"

BASEDIR=$(dirname $0)
cd $BASEDIR
cd ..
make clean "SRCDIR=${SRCDIR}"

