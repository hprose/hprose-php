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
  sudo apt-get install -y hhvm-dev
  sudo apt-get install -y libgmp-dev
  sudo apt-get install -y libmpfr4 libmpfr-dev
  sudo apt-get install -y libmpc-dev libmpc2
  sudo apt-get install -y libtool
  sudo apt-get install -y m4
  sudo apt-get install -y bison
  sudo apt-get install -y flex
  sudo apt-get install -y autoconf
  sudo add-apt-repository -y ppa:ubuntu-toolchain-r/test
  sudo apt-get update
  sudo apt-get install -y gcc-4.8 g++-4.8 gcc-4.8-multilib g++-4.8-multilib
  sudo update-alternatives --install /usr/bin/gcc gcc /usr/bin/gcc-4.8 20
  sudo update-alternatives --install /usr/bin/g++ g++ /usr/bin/g++-4.8 20
  sudo update-alternatives --config gcc
  sudo update-alternatives --config g++
  sudo apt-get update
  sudo apt-get upgrade -y
  sudo apt-get dist-upgrade -y
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
