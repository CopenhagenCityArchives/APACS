These tests are designed to do checks of concrete API calls to kbhkilder.dk/api.
The tests not only checks the generic endpoints of the API, but also runs tests to check if concrete endpoints such as http://www.kbhkilder.dk/api/metadata/2/road_name actually returns data, and if this data can be used to do concrete searches.

Run the tests by using this command:

jasmine-node /tests