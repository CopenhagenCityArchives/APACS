FROM alpine:latest

RUN apk add lftp
RUN apk add openssh

#ENTRYPOINT lftp -e "mirror -R --delete --exclude-glob **/.env --exclude-glob  /vendor/**/test**/*.php --exclude-glob *.yml --exclude-glob tests_api/** --no-empty-dirs --depth-first  --use-pget-n=15 --verbose=3 /code ${KBHKILDER_FTP_PATH}" -u ${KBHKILDER_FTP_USER},${KBHKILDER_FTP_PASSWORD} ${KBHKILDER_FTP_HOST}
# --verbose=3
ENTRYPOINT lftp -c "set ftp:ssl-force true;set sftp:auto-confirm true; set log:enabled false; open sftp://${KBHKILDER_FTP_HOST} -u ${KBHKILDER_FTP_USER},${KBHKILDER_FTP_PASSWORD}; ls -l; mirror -R --delete --exclude-glob **/.env --exclude-glob /vendor/**/test**/*.php --exclude-glob tests_api/** --exclude-glob tests/** --exclude-glob *.yml --exclude-glob composer.lock --exclude-glob composer.json --exclude-glob composer.phar --exclude-glob phpunit.xml --exclude-glob .dockerignore  --no-empty-dirs --depth-first  --use-pget-n=15  /code ${KBHKILDER_FTP_PATH}; quit;"
