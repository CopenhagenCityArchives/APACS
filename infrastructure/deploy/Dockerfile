FROM alpine:latest

RUN apk add lftp

ENTRYPOINT lftp -e "mirror -R --use-pget-n=15 --verbose=1 --no-perms /code ${KBHKILDER_FTP_PATH}" -u ${KBHKILDER_FTP_USER},${KBHKILDER_FTP_PASSWORD} ${KBHKILDER_FTP_HOST}
