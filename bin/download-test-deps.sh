#!/usr/bin/env bash

TMPDIR=${TMPDIR-/tmp}
if [ ! -d "$TMPDIR" ]; then
	mkdir -p "$TMPDIR"
fi

WORDPRESS_DIR="$TMPDIR/wordpress"
if [ ! -d "$WORDPRESS_DIR" ]; then
	echo 'Distination path is not a directory'
	exit 1
else
	mkdir -p "$WORDPRESS_DIR/wp-content/mu-plugins"
fi

URLS=('https://downloads.wordpress.org/plugin/contact-form-7.6.1.3.zip'
	'https://downloads.wordpress.org/plugin/ninja-forms.3.13.0.zip'
	'https://www.codeccoop.org/formsbridge/plugins/wpforms.zip'
	'https://www.codeccoop.org/formsbridge/plugins/gravityforms.zip')

PLUGINS=('contact-form-7' 'gravityforms' 'ninja-forms' 'wpforms')

COUNT=${#PLUGINS[@]}

i=0
while [ $i -lt $COUNT ]; do
	URL=${URLS[$i]}
	PLUGIN=${PLUGINS[$i]}

	ZIP="$TMPDIR/$PLUGIN.zip"
	if [ ! -f "$ZIP" ]; then
		curl -sL --connect-timeout 5 --max-time 30 "$URL" >"$ZIP"

		if [ $? -gt 0 ]; then
			test -f "$ZIP" && rm -rf "$ZIP"
			echo "Download of $PLUGIN has failed"
			exit 1
		fi
	fi

	PLUGIN_DIR="$WORDPRESS_DIR/wp-content/mu-plugins"
	if [ ! -d "$PLUGIN_DIR" ]; then
		unzip -oqq "$ZIP" -d "$PLUGIN_DIR"
	fi

	i=$((i + 1))
done
