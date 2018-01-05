<?php


/* Define delle tables */

define("TBL_OBJECTS", 'objects');
define("TBL_LAMPS",   'lamps');
define("TBL_DEVICES", 'devices');
define("TBL_AREAS",   'areas');
define("TBL_SENSORS",   'sensors');
define("TBL_LABELS",   'labels');
define("TBL_COUNTERS",   'energycounters');


/* Define di tipo di object */

define("OBJ_AREA",    'area'  );
define("OBJ_SENSOR",  'sensor'  );
define("OBJ_LAMP",    'lamp'  );
define("OBJ_EMERGENCYLAMP",    'emergencylamp'  );
define("OBJ_DEVICE",  'device');
define("OBJ_CTRL_LABEL", 'ctrl_label');
define("OBJ_ENERGY_CTR", 'energycounter');


/* Define di tipo di devices */

define("BECKHOFF_PLC",              'beckhoff'  );
define("TERACOM_CONTROL",           'teracom_control'  );
define("CX_TOUCHPANEL",             'touchpanel'  );
define("DATA_LOGGER",               'data-logger'  );
define("DYNALITE_CONTROLLER",       'controller'  );
define("RIELLO_NETMAN204",          'riello_netman204'  );
define("ZETAQLAB_RFSERVER",         'rfserver'  );
define("ZETAQLAB_DALISERVER",       'daliserver'  );
define("ZETAQLAB_ZQSENSE",          'zqsense'  );

function get_table_by_object_type($objecttype){
    
    if($objecttype == OBJ_AREA)         return TBL_AREAS;
    if($objecttype == OBJ_EMERGENCYLAMP)         return TBL_LAMPS;
    if($objecttype == OBJ_LAMP)         return TBL_LAMPS;
    if($objecttype == OBJ_DEVICE)       return TBL_DEVICES;
    if($objecttype == OBJ_SENSOR)       return TBL_SENSORS;
    if($objecttype == OBJ_CTRL_LABEL)       return TBL_LABELS;
    if($objecttype == OBJ_ENERGY_CTR)       return TBL_COUNTERS;
    
    return  TBL_OBJECTS;
}

function get_devices_types(){
    return  [RIELLO_NETMAN204, ZETAQLAB_ZQSENSE];
}

?>


