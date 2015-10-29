/*
  Testing APACS API.
  Run: jasmine-node ./tests
  These tests runs through all collection, receiving data from all searchable metadata levels
  and then receives data, both by parameters and by id.
  Problems: If no data is received, only a notification is sent.
*/

var frisby = require('frisby');

//var url = 'http://www.kbhkilder.dk/api';
var url = 'http://192.168.10.129/api';
var collectionsToTest = [2,3,4,5,6];


var runTests = function(){
  var collections = collectionsToTest;

  for(var i = 0; i < collections.length; i++){
    var test = new collectionTraverser(collections[i]);
    test.run();
  }  
};

var collectionTraverser = function(id){
  this.collectionId = id;
};

collectionTraverser.prototype.run = function(){
  var that = this;
  frisby.create("Walkthrough: Get all metadata levels and data for collection " + that.collectionId)
  .get(url + '/collections/' + that.collectionId)
  .afterJSON(function(data){
    data[0].levels.sort(function(a, b){return a.order-b.order;});
    var levels = data[0].levels;

    //Setup
    //Creating objects
    var testRunners = [];
    for(var i = 0; i < levels.length; i++){
      //We only test levels that are searchable
      if(levels[i].searchable == true)
      testRunners.push(new testRunner(that.collectionId, levels[i].name, url));
    }

    //Settings nexts
    for(var i = 0; i < levels.length; i++){
      if(testRunners[i+1] !== undefined){
        testRunners[i].setNext(testRunners[i+1]);
      }
    }

    //Start the first, and the rest will RUN!
    testRunners[0].setData(undefined);
    testRunners[0].setQueryString(undefined);

    testRunners[0].run();
  })
  .toss();
};

var testRunner = function(collectionId, levelName, url){
  this.collectionId = collectionId;
  this.levelName = levelName;
  this.data = "";
  this.next = false;
  this.url = url;
  this.queryString = "";
};

testRunner.prototype.setData = function(data){
  this.data = data;
};

testRunner.prototype.setQueryString = function(qs, prevlevelName){
  if(this.data && prevlevelName)
    this.queryString = qs + prevlevelName + '=' + this.data + "&";
};

testRunner.prototype.setNext = function(next){
  this.next = next;
};

testRunner.prototype.run = function(){
  var that = this;
  var getUrl = that.url + '/metadata/' + that.collectionId + '/' + that.levelName + '?' + that.queryString;
  console.log("Getting metadata: Collection " + that.collectionId + ', ' + that.levelName);
  
  frisby.create('Walkthrough: ' + that.queryString)
    .get(getUrl)
    .expectStatus(200)
    .afterJSON(function(data){
      expect(data.length).toBeDefined();
      if(that.next){
        that.next.setData(data[0].text);
        that.next.setQueryString(that.queryString, that.levelName);
        that.next.run();
      }
      else{
        //Run data call!
        if(data.length > 0){
          frisby.create("Walkthrough: Getting data")
          .get(url + '/data/' + that.collectionId + '?' + that.queryString + that.levelName + '=' + data[0].text)
          .expectStatus(200)
          .afterJSON(function(data){

            expect(data.length).toBeDefined();

            if(data.length > 0){
              frisby.create("Walkthrough: Getting data by id")
              .get(url + '/data/' + that.collectionId + '?id=' + data[0].id)
              .expectStatus(200)
              .expectJSONTypes('0', {
                id: String,
                metadata: Object,
                images: Array
              })
              .toss();
            }
            else{
              console.log("No data found. Search for data by id: Collection " + that.collectionId);
            }
          })
          .toss();
        }
        else{
          console.log("No data found: Search for data by parameters: " + getUrl);
        }
      }
    })
    .toss();
};

runTests();