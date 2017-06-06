<!DOCTYPE html>
<html>
    <head>
        <title>API for <?php echo $obj['long_name']; ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <!-- Latest compiled and minified CSS, Twitter Bootstrap -->
        <link rel="stylesheet" href="http://netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">
    </head>
    <body>
        <div class="container">
            <div class="row-fluid">
                <span class="span9">
                    <h2>API for <?php echo $obj['long_name']; ?></h2>
                    <!--<p>
                        <span class="label label-success">Service</span> <strong>http://www.kbhkilder.dk/api/collections/<?php echo $obj['id']; ?></strong>
                    </p>-->
                    <p><?php echo $obj['info']; ?></p>
                    <!--<span class="label label-info">Eksempel</span>
                    <a target="_blank" href="http://www.kbhkilder.dk/api/collections">http://www.kbhkilder.dk/api/collections/<?php echo $obj['id']; ?></a>-->
                </span>
                <?php if(!is_null($obj['stats'])){ ?>
                <div class="span9">&nbsp;</div>
                <span class='span9'>
                    <h2>Protokoller og sider (total/publiceret)</h2>
                </span>
                <div class="span9">
                    <p>Protokoller: <b><?php echo number_format($obj['stats']['public_units'], 0, ',', '.'); ?> / <?php echo number_format($obj['stats']['units'], 0, ',', '.'); ?></b></p>
                    <p>Sider: <b><?php echo number_format($obj['stats']['public_pages'], 0, ',', '.'); ?> / <?php echo number_format($obj['stats']['pages'], 0, ',', '.'); ?></b></p>
                    <?php if($obj['stats']['units_without_pages'] > 0){ ?>
                        <p>Der er <?php echo $obj['stats']['units_without_pages']; ?> protokoller uden tilknyttede sider</p>
                    <?php } ?>
                </div>
                <?php } ?>
                <div class="span9">&nbsp;</div>
                <span class='span9'>
                    <h2>Metadata</h2>
                </span>
                <?php if($obj['levels']){ foreach($obj['levels'] as $level){ ?>
                <?php if($level['searchable']){ ?>
                <div class="span9">
                    <h4><?php echo $level['gui_name']; ?></h4>
                    <p>
                        <span class="label label-success">Service</span> <strong><?php echo $level['url'] ?></strong>
                    </p>
                    <p><?php echo $level['gui_description']; ?></p>
                    <p>
                        Påkrævede parametre:
                    </p>
                    <?php if(($level['required_levels'])) { ?>
                    <ul>
                        <?php foreach($level['required_levels'] as $filter){ ?>
                            <li><strong>:<?php echo $filter; ?></strong> <?php echo $level['api_description']; ?></li>
                        <?php } ?>
                    </ul>
                    <?php } else{ ?>
                    <ul>
                        <li>Ingen</li>
                    </ul>
                    <?php } ?>
                    <span class="label label-info">Eksempel</span>
                    <a target="_blank" href="<?php echo $level['url'] ?><?php echo $level['required_levels_url']; ?>"><?php echo $level['url'] ?><?php echo $level['required_levels_url']; ?></a>
                    <div class="span9">&nbsp;</div>
                </div>
                <?php } ?>
                <?php } ?>
                <?php } ?>
                <div class="span9">
                    <h2>Protokolsider</h2>
                    <p>
                        <span class="label label-success">Service</span> <strong><?php echo $obj['data_url']; ?></strong>
                    </p>
                    <p>Henter siderne for en given collection baseret på filtre.</p>
                    <p>
                        Parametre:
                    </p>
                    <?php if(($obj['data_filters'])) { ?>
                    <ul>
                        <?php foreach($obj['data_filters'] as $filter){ ?>
                            <?php if($filter['searchable']){ ?>
                            <li><strong>:<?php echo $filter['name']; ?></strong> <?php echo $filter['api_description']; ?> <?php if($filter['required']){?>  <span class="label label-warning">Påkrævet</span> <?php } ?></li>
                            <?php } ?>
                        <?php } ?>
                    </ul>
                    <?php } else{ ?>
                    <ul>
                        <li>Ingen</li>
                    </ul>
                    <?php } ?>
                    <span class="label label-info">eksempel</span>
                    <a target="_blank" href="<?php echo $obj['data_url']; ?>"><?php echo $obj['data_url']; ?></a>
                </div>
                <?php if(count($obj['indexes'])> 0){ ?>
                <div class="span9">&nbsp;</div>
                <div class='span9'>
                    <h2>Indeksering</h2>
                    <p>Indekseringen er konfigureret i tre lag:
                    <ul>
                        <li><strong>Entry</strong>: Én måde at registrere samlingen på. Eksempelvis er registreringen af personer i denne samling en entry.</li>
                        <li><strong>Entity</strong>: En delinformation på en kilde. En entity kan eksempelvis være en person eller en adresse.</li>
                        <li><strong>Felter</strong>: Hver entity består af et eller flere felter. Eksempelvis fornavn, efternavn og fødested.</li>
                    </ul>Indeksering er kun muligt hvis brugeren er logget ind.</p>
                    <?php foreach($obj['indexes'] as $index){ ?>
                        <h3>Entry: <?php echo $index['guiName']; ?></h3>
                        <p><?php echo $index['info']; ?> Sidelayout: <?php echo $index['layout_columns']; ?>x<?php echo $index['layout_rows']; ?>.</p>
                        <?php foreach($index['entities'] as $entity){ ?>
                            <h4>Entity: <?php echo $entity['guiName']; ?></h4>
                            <p>
                                <span class="label label-success">Service</span> <strong><?php echo $entity['serviceUrl'] ?></strong>
                            </p>
                            <p>Nyoprettelse sker med POST, opdateringer med PUT (med angivelse af postens <strong>id</strong>). Maksimalt antal per entry: <?php echo $entity['countPerEntry']; ?>.</p>
                            <p>Parametre:</p>
                            <ul>
                                <?php foreach($entity['fields'] as $field){ ?>
                                    <li><strong>:<?php echo $field['name']; ?></strong> <?php if($field['required'] == true){?>  <span class="label label-warning">Påkrævet</span> <?php } ?></li>
                                <?php } ?>
                            </ul>
                        <?php } ?>
                    <?php } ?>
                </div>
                <?php } ?>
            </div>
        </div>
    </body>
</html>
