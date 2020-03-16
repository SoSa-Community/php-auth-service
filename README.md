# SoSa auth Service

Backend Auth service

```
docker build . -t sosa/auth-service
docker run -d -p 4606:3306 -p 4607:80 -p 4608:443 -p 4609:8090 --name "sosa-auth-server" --rm -v "E:/Development/sosa-auth-service/server/":/var/www/html:Z -t sosa/auth-service

mv server/config/config.example.php server/config/config.php

docker exec -w /var/www/html -it sosa-auth-server composer install

docker exec -it sosa-auth-server bash

```

### Optimization for production
`docker exec -w /var/www/html -it sosa-auth-server composer install --optimize --no-dev --classmap-authoritative`



