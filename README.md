# cloudMQTTSubscriber

A simple php subscriber for mysql queries.
It works with cloudMQTT broker.


Esempio per Messaggio per query request
{"cmd":"request","session":"eventsRequest","data":{"action":"query","sql":"select ID, IDObject, Description, Epoch, Status from events where status > 127 order by ID desc limit 0, 30"}}
