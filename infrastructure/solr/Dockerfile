FROM library/solr:6.6.3

COPY solr_conf/ /opt/solr/server/solr/configsets/solr_conf/
COPY web.xml /opt/solr/server/solr-webapp/webapp/WEB-INF/web.xml
##COPY security.json /opt/solr/server/solr/security.json

#Set Java HEAP memory
RUN echo "SOLR_JAVA_MEM=\"-Xms2048m -Xmx2048m\"" >> /opt/solr/bin/solr.in.sh

RUN /opt/solr/bin/solr start && /opt/solr/bin/solr stop
