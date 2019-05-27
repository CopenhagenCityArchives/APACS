FROM alpine:latest

RUN apk add lftp

ENTRYPOINT lftp -e "mirror -R --delete --exclude-glob **/.env --use-pget-n=15 --verbose=3 /code ${KBHKILDER_FTP_PATH}" -u ${KBHKILDER_FTP_USER},${KBHKILDER_FTP_PASSWORD} ${KBHKILDER_FTP_HOST}


