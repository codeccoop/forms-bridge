#!/usr/bin/env bash

WORDPRESS_DIR=$1

if [ -z "$WORDPRESS_DIR" ]; then
	WORDPRESS_DIR='/tmp/wordpress'
fi

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

	curl -sL --connect-time 10 "$URL" >"$TEMP_DIR/$PLUGIN.zip"
	unzip -qq "$TEMP_DIR/$PLUGIN.zip" -d "$WORDPRESS_DIR/wp-content/mu-plugins"

	i=$((i + 1))
done
