#!/usr/bin/env bash

echo 'Hello'

TMPDIR=${TMPDIR-/tmp}
if [ ! -d "$TMPDIR" ]; then
	echo "Make tmp dir on $TMPDIR"
	mkdir -p "$TMPDIR"
fi

WORDPRESS_DIR="$TMPDIR/wordpress"
if [ ! -d "$WORDPRESS_DIR" ]; then
	echo 'Distination path is not a directory'
	exit 1
else
	echo "Make mu-plugins dir on $WORDPRESS_DIR/wp-content/mu-plugins"
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

	echo "$PLUGIN: $URL"
	curl -sL --connect-time 10 "$URL" >"$TMPDIR/$PLUGIN.zip"
	unzip -qq "$TMPDIR/$PLUGIN.zip" -d "$WORDPRESS_DIR/wp-content/mu-plugins"

	i=$((i + 1))
done
