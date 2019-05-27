FROM alpine:latest

RUN apk add lftp

ENTRYPOINT lftp -e "mirror -R --delete --exclude-glob **/.env --exclude-glob  /vendor/**/test**/*.php --exclude-glob *.yml --exclude-glob tests_api/** --no-empty-dirs --depth-first  --use-pget-n=15 --verbose=3 /code ${KBHKILDER_FTP_PATH}" -u ${KBHKILDER_FTP_USER},${KBHKILDER_FTP_PASSWORD} ${KBHKILDER_FTP_HOST}


