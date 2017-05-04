#!/bin/bash

# Download release of Oyst PHP SDK

GitHub_Owner="OystParis"
GitHub_Repo="oyst-php"
GitHub_Release=

# Do not change under this comment

ScriptDir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd $ScriptDir

if [ -n "$GitHub_Release" ]; then
    # Specified release url
    GitHubProjectReleaseUrl=https://api.github.com/repos/$GitHub_Owner/$GitHub_Repo/tarball/$GitHub_Release
else
    # Latest release url
    GitHubProjectReleaseUrl=$(curl -s https://api.github.com/repos/$GitHub_Owner/$GitHub_Repo/releases/latest | grep 'tarball_url' | cut -d\" -f4)
fi

# Download release
curl -sL $GitHubProjectReleaseUrl | tar xz

cd $GitHub_Owner-$GitHub_Repo-*
if [ ! -f "README.md" ]; then
    echo "Oyst SDK download error."
    exit 1
fi
cd ..

mv $GitHub_Owner-$GitHub_Repo-* oyst-php && cd oyst-php

echo "Oyst SDK is downloaded in $ScriptDir."

# Composer Install
composer install --no-dev
echo "Composer install done."
exit 0
