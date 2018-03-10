# cloudMQTTSubscriber

A simple php subscriber for mysql queries.
It works with cloudMQTT broker.


Esempio per Messaggio per query request 
{"cmd":"request",app:"clight","session":"eventsRequest","action":"query","data":{"sql":"select ID, IDObject, Description, Epoch, Status from events where status > 127 order by ID desc limit 0, 30"}}

{"cmd":"request",app:"rfserver","session":"dev","action":"update_node", "data":{ "address":"65614"},}