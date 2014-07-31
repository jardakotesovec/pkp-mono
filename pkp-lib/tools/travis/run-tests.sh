#!/bin/bash

# @file tools/travis/run-tests.sh
#
# Copyright (c) 2014 Simon Fraser University Library
# Copyright (c) 2010-2014 John Willinsky
# Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
#
# Script to run data build, unit, and integration tests.
#

set -e

export DUMMYFILE=~/dummy.pdf
export BASEURL="http://localhost"
export DBHOST=localhost
export DBNAME=ojs-ci
export DBUSERNAME=ojs-ci
export DBPASSWORD=ojs-ci
export FILESDIR=files

# Generate a sample PDF file to use for testing.
sudo apt-get install a2ps
echo "This is a test" | a2ps -o - | ps2pdf - ~/dummy.pdf

# Create the database.
if [[ "$DB" == "pgsql" ]]; then
	psql -c "CREATE DATABASE \"ojs-ci\";" -U postgres
	psql -c "CREATE USER \"ojs-ci\" WITH PASSWORD 'ojs-ci';" -U postgres
	psql -c "GRANT ALL PRIVILEGES ON DATABASE \"ojs-ci\" TO \"ojs-ci\";" -U postgres
	export DBTYPE=MySQL
elif [[ "$DB" == "mysql" ]]; then
	mysql -u root -e 'CREATE DATABASE `ojs-ci` DEFAULT CHARACTER SET utf8'
	mysql -u root -e "GRANT ALL ON \`ojs-ci\`.* TO \`ojs-ci\`@localhost IDENTIFIED BY 'ojs-ci'"
	export DBTYPE=PostgreSQL
fi

# Prep files
cp config.TEMPLATE.inc.php config.inc.php
mkdir ${FILESDIR}
sudo chown -R travis:www-data .

# Run data build suite
./lib/pkp/tools/runAllTests.sh -b

# Run unit test suite.
./lib/pkp/tools/runAllTests.sh -Cc
# Functional tests temporarily disabled
# - ./lib/pkp/tools/runAllTests.sh -f
