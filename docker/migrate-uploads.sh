#!/bin/sh
set -eu

source_directory="${UPLOAD_SOURCE:-/source}"
target_directory="${UPLOAD_TARGET:-/var/www/html/storage/app/public}"
upload_owner="${UPLOAD_OWNER:-www-data:www-data}"

test -d "$source_directory"
test -d "$target_directory"

if [ -n "$(find "$source_directory" "$target_directory" -type l -print -quit)" ]; then
    echo 'Upload migration refuses symlinks.' >&2
    exit 1
fi

find "$source_directory" -type f -exec sh -ec '
    source_directory=$1
    target_directory=$2
    shift 2
    for source_file do
        relative_path=${source_file#"$source_directory"/}
        target_file="$target_directory/$relative_path"
        if [ -e "$target_file" ] && ! cmp -s "$source_file" "$target_file"; then
            echo "Upload conflict: $target_file" >&2
            exit 1
        fi
    done
' sh "$source_directory" "$target_directory" {} +

cp -a "$source_directory/." "$target_directory/"
chown -R "$upload_owner" "$target_directory"
chmod 755 "$target_directory"
