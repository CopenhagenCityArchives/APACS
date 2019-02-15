FROM python:3

WORKDIR /usr/src/app


# Install requirements
COPY ./indexing/requirements.txt ./
RUN pip install --no-cache-dir -r requirements.txt

#Add source code
COPY ./indexing ./

ENTRYPOINT [ "python", "/usr/src/app/cron.py" ]
