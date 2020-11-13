FROM library/solr

COPY solr_conf/ /opt/solr/server/solr/configsets/solr_conf/
COPY web.xml /opt/solr/server/solr-webapp/webapp/WEB-INF/web.xml

#Set Java HEAP memory
#RUN echo "SOLR_JAVA_MEM=\"-Xms1400m -Xmx1400m\"" >> /opt/solr/bin/solr.in.sh

##Create banana-int core for saving and loading Banana dashboards
RUN /opt/solr/bin/solr start && \
    /opt/solr/bin/solr create_core -c banana-int && \
    /opt/solr/bin/solr stop

COPY --chown=solr:solr security.json /opt/solr/server/solr/security.json

ENTRYPOINT [ "docker-entrypoint.sh", "solr-precreate", "apacs_core", "/opt/solr/server/solr/configsets/solr_conf/apacs_core", "-Ddisable.configEdit=true" ]

# Precreate both cores
#ENTRYPOINT [ "bash", "-c", "precreate-core apacs_core /opt/solr/server/solr/configsets/solr_conf/apacs_core;", "precreate-core banana-int;", "-Ddisable.configEdit=true"]