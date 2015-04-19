/*
  Testing ksa_backend API.
  Run: jasmine-node ./tests
  The test are based on concrete data of collection 2.
*/

var frisby = require('frisby');

var url = 'http://www.kbhkilder.dk/api';
//var url = 'http://192.168.10.129/api';

frisby.create('Collection metadata')
  .get(url + '/collections/2')
  .expectStatus(200)
  .afterJSON(function(data){
    expect(data.length > 0).toBe(true);
  })
.toss();

frisby.create('Collection metadata error')
  .get(url + '/collections/thisIsIncorrect')
  .expectStatus(400)
.toss();

frisby.create('Collection levels, all levels')
  .get(url + '/levels/2')
  .expectStatus(200)
  .afterJSON(function(data){
      expect(data.length).toBeGreaterThan(0);
  })
.toss();

frisby.create('Collection levels error')
  .get(url + '/levels/12321')
  .expectStatus(400)
.toss();

frisby.create('Collection levels, specific level')
  .get(url + '/levels/2/road_name')
  .expectStatus(200)
  .afterJSON(function(data){
    expect(data).not.toBe(undefined);
  })
.toss();

frisby.create('Collection levels, specific level error')
  .get(url + '/levels/2/thisIsIncorrect')
  .expectStatus(400)
.toss();

frisby.create('Level metadata')
  .get(url + '/metadata/2/road_name')
  .expectStatus(200)
  .afterJSON(function(data){
    expect(data.length).toBeGreaterThan(0);
  })
.toss();

frisby.create('Level metadata, error')
  .get(url + '/levels/2/thisIsIncorrect')
  .expectStatus(400)
.toss();

frisby.create('Level metadata, with required super filter')
  .get(url + '/metadata/2/year?road_name=Absalonsgade')
  .expectStatus(200)
  .afterJSON(function(data){
    expect(data.length).toBeGreaterThan(0);
  })
.toss();

frisby.create('Level metadata, error')
  .get(url + '/metadata/2/year')
  .expectStatus(400)
.toss();

frisby.create('Data with required filters')
  .get(url + '/data/2?road_name=Absalonsgade&year=1901&month=maj')
  .expectStatus(200)
  .afterJSON(function(data){
    expect(data.length).toBeGreaterThan(0);
  })
.toss();

frisby.create('Data, required filter not set')
  .get(url + '/data/2?year=1901&month=maj')
  .expectStatus(400)
.toss();