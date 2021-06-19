require('should');

const zapier = require('zapier-platform-core');

// Use this to make test calls into your app:
const App = require('../index');
const appTester = zapier.createAppTester(App);

describe('My App', () => {

  it('should test something', (done) => {
    const x = 1;
    x.should.eql(1);
    done();
  });

});
