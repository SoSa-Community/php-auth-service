FROM centos:centos7
MAINTAINER James Mahy <james.mahy@cevo.co.uk>

# Install varioius utilities
RUN yum -y install curl wget unzip git vim nano \
iproute python-setuptools hostname inotify-tools yum-utils which \
epel-release

# Install Python and Supervisor
RUN yum -y install python-setuptools \
&& mkdir -p /var/log/supervisor \
&& easy_install supervisor

# Install Remi Updated PHP 7
RUN wget http://rpms.remirepo.net/enterprise/remi-release-7.rpm \
&& rpm -Uvh remi-release-7.rpm \
&& yum-config-manager --enable remi-php74 \
&& yum -y install php php-devel php-gd php-pdo php-soap php-xmlrpc php-xml php-phpunit-PHPUnit php-mysql

COPY Ubiquity.conf /etc/httpd/conf.d/ubiquity.conf

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Ubiquity Dev tools
RUN composer global require phpmv/ubiquity-devtools
RUN echo "export PATH=/root/.composer/vendor/bin:\$PATH" > /root/.bashrc

# Install MariaDB
COPY MariaDB.repo /etc/yum.repos.d/MariaDB.repo
RUN yum clean all;yum -y install mariadb-server mariadb-client
VOLUME /var/lib/mysql

# Install Redis
RUN yum -y install redis;

# UTC Timezone & Networking
RUN ln -sf /usr/share/zoneinfo/UTC /etc/localtime \
	&& echo "NETWORKING=yes" > /etc/sysconfig/network

COPY supervisord.conf /etc/supervisord.conf

CMD ["/usr/bin/supervisord"]

EXPOSE 80
EXPOSE 443
EXPOSE 3000
EXPOSE 3001
EXPOSE 3002
EXPOSE 3306
EXPOSE 8090