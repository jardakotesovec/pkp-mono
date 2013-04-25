#!/bin/bash -e

# This script is used to pull article metadata XML files from
# OJS installations. You should schedule it via cronjob. We
# recommend running this script once a day during off-hours.
#
# Usage:
#   pull.sh [path/to/pull.conf]
#
# If no configuration file is given then the file will use
# the lucene plugin's default configuration file:
# "plugins/generic/lucene/embedded/etc/pull.conf". For more
# information see the inline comments in the default configuration
# file.

# Source common variables.
EXEC_PATH=`dirname $0`
source "$EXEC_PATH/script-startup"

# Identify the configuration file.
if [ -z "$1" ]; then
  CONFIG_FILE="$PLUGIN_DIR/embedded/etc/pull.conf"
else
  CONFIG_FILE="$1"
fi

# If no configuration file can be found then exit.
if [ ! -r "$CONFIG_FILE" ]; then
  echo "Configuration file '$CONFIG_FILE' not found."
  exit 1
fi

# Get the configuration.
source "$CONFIG_FILE"

# Make sure that the configured staging directory
# exists or can be created.
if [ ! -e "$PULL_STAGING_DIR" ]; then
  # Try to create the staging directory.
  if mkdir -p "$PULL_STAGING_DIR" >/dev/null 2>&1 ; then
    true;
  else
    echo "The staging directory '$PULL_STAGING_DIR' is missing"
    echo "and its parent directory is not writable so that we cannot create it."
    exit 1
  fi
fi

# Make sure that the staging directory is writable.
if [ ! -w "$PULL_STAGING_DIR" ]; then
  echo "The staging directory '$PULL_STAGING_DIR' is not writable"
  exit 1
fi

# Make sure we have a valid endpoint URLs.
PULL_URLS=`echo $PULL_ENDPOINTS | sed 's/|/ /g'`
for PULL_URL in $PULL_URLS; do
  if echo "$PULL_URL" | egrep -i "$URL_PATTERN" >/dev/null; then
    true;
  else
    echo "Invalid pull endpoint URL '$PULL_URL' found in configuration"
    echo "file '$CONFIG_FILE'."
    echo "Please configure a valid URL."
    exit 1
  fi
done

# For the usage statistics update:
# Identify the next file name (required for compatibility with the corresponding embedded
# server behavior so that one can switch between the two).
CURRENT_STAT_FILE=`ls $PULL_LUCENE_INDEX_DIR/external_usageMetric.* 2>/dev/null | sort | tail -n1`
CURRENT_STAT_EXTENSION=`echo $CURRENT_STAT_FILE | sed -r 's/.*\.0*([1-9][0-9]*)$/\1/'`
NEXT_STAT_EXTENSION=$((CURRENT_STAT_EXTENSION+1)) # This works even if no prior file was found...
NEXT_STAT_EXTENSION=`printf %08d $NEXT_STAT_EXTENSION` # Padding to eight numbers.
NEXT_STAT_FILE="$PULL_LUCENE_INDEX_DIR/external_usageMetric.$NEXT_STAT_EXTENSION"
UPDATED_STAT_FILE='false'

for PULL_URL in $PULL_URLS; do
  # Pull and stage article metadata files.
  echo "Accessing '$PULL_URL':"
  TIMESTAMP=`date '+%Y%m%d%H%M%S'`
  PREFIX=`echo $PULL_URL | sed -r 's%^https?://%%;s%/index.php%%;s%[/.:]%-%g'`
  HAS_MORE=yes
  COUNTER=0
  while [ "$HAS_MORE" = yes ]; do
    SUFFIX=`printf %03d $COUNTER`
    FILENAME="$PULL_STAGING_DIR/$PREFIX-$TIMESTAMP-$SUFFIX.xml"
    curl -s "$PULL_URL/index/lucene/pullChangedArticles" >"$FILENAME"
    echo " - Pulling '$FILENAME'."
    HAS_MORE=`cat "$FILENAME" | egrep '<articleList[^>]* hasMore="(yes|no)"' | sed -r 's/^.*hasMore="(yes|no)".*$/\1/'`
    let COUNTER=COUNTER+1
  done
  echo " - $COUNTER file(s) pulled from '$PULL_URL'."

  # Update usage statistics.
  echo " - Checking usage statistics."
  # Download usage statistics to a temporary file.
  TEMPFILE=`tempfile`
  curl -s "$PULL_URL/index/lucene/usageMetricBoost" >$TEMPFILE
  if [[ -s $TEMPFILE ]]; then
    if [[ ! -z "$CURRENT_STAT_FILE" ]]; then
      # Copy the old file to the new location while suppresing all
      # entries from the same installation.
      INST_ID=`head -n1 "$TEMPFILE" | sed -r 's/-[0-9]+=[0-9.]+$//'`
      sed -r "/^$INST_ID-[0-9]+=/d" $CURRENT_STAT_FILE >>$TEMPFILE
      UPDATED_STAT_FILE='true'
    fi
    LC_ALL=C sort $TEMPFILE >$NEXT_STAT_FILE
    echo " - Updated statistics to '$NEXT_STAT_FILE'."
  else
    echo " - No statistics found or statistics disabled."
  fi
  rm $TEMPFILE
  echo
done

if [[ $UPDATED_STAT_FILE = "true" ]]; then
  # Tell Solr to refresh usage statistics data.
  curl -s $RELOAD_EXT_FILE_ENDPOINT >/dev/null
fi
