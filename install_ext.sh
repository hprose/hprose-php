if [[ $TRAVIS_PHP_VERSION = '5.3' ]]; then
  wget https://pecl.php.net/get/libevent-0.1.0.tgz
  tar zxvfp libevent-0.1.0.tgz
  cd libevent-0.1.0
  phpize
  ./configure
  make
  make install
  echo "extension = libevent.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
elif [[ $TRAVIS_PHP_VERSION = 'hhvm' ]]; then
  sudo apt-get install hhvm-dev
  git clone https://github.com/chobie/hhvm-uv.git --recursive
  cd hhvm-uv
  make -C libuv CFLAGS=-fPIC
  hphpize
  cmake -D CMAKE_BUILD_TYPE=Debug . && make
else
  git clone https://bitbucket.org/osmanov/pecl-event.git
  cd pecl-event
  phpize
  ./configure --with-event-core --with-event-extra --enable-event-debug
  make
  make install
  echo "extension = event.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
fi
cd ..
