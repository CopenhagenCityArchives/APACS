var frisby = require('frisby');

var url = 'http://127.0.0.1:8080';

frisby.create('Tasks')
  .get(url + '/tasks')
  .expectStatus(200)
  .afterJSON(function(data){
    expect(data.length > 0).toBe(true);
  })
.toss();

frisby.create('Task')
  .get(url + '/tasks/1')
  .expectStatus(200)
  .afterJSON(function(data){
    expect(data).not.toBe(undefined);
  })
.toss();

frisby.create('taskschema')
  .get(url + '/taskschema?task_id=1')
  .expectStatus(200)
  .afterJSON(function(data){
    expect(data.schema).not.toBe(undefined);
  })
.toss();


frisby.create('Units')
  .get(url + '/units?task_id=1&collection_id=1')
  .expectStatus(200)
  .afterJSON(function(data){
    expect(data.length > 0).toBe(true);
  })
.toss();

frisby.create('Unit')
  .get(url + '/units/1')
  .expectStatus(200)
  .afterJSON(function(data){
    expect(data.id).not.toBe(undefined);
  })
.toss();

frisby.create('Pages')
  .get(url + '/pages?unit_id=1')
  .expectStatus(200)
  .afterJSON(function(data){
    expect(data.length > 0).toBe(true);
  })
.toss();

frisby.create('Page')
  .get(url + '/pages/1')
  .expectStatus(200)
  .afterJSON(function(data){
    expect(data.id).not.toBe(undefined);
  })
.toss();

frisby.create('Next available page')
  .get(url + '/pages/nextavailable?task_id=1&unit_id=1&page_number=1')
  .expectStatus(200)
  .afterJSON(function(data){
    expect(data.length > 0).toBe(true);
  })
.toss();

frisby.create('EntriesPost')
  .post(url + '/entries/1')
  .expectStatus(401)
.toss();

frisby.create('Datasource')
  .get(url + '/datasource/1')
  .expectStatus(200)
  .afterJSON(function(data){
    expect(data.length > 0).toBe(true);
  })
.toss();