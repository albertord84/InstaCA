import React, { Component } from 'react';
import { HashRouter, Route, Link, Redirect } from 'react-router-dom';
import { browserHistory } from 'react-router';

import * as _ from 'lodash';

import Location from './Location';
import Hashtag from './Hashtag';

class Home extends Component {
    componentDidMount() {
        window.location.href = '#/location';
    }
    render() {
        return (
            <HashRouter>
                <div>
                    <div className="d-flex flex-column flex-md-row align-items-center p-3 px-md-4 mb-3 bg-white border-bottom box-shadow">
                        <nav className="my-2 my-md-0 mr-md-3">
                            <Link to='/location' className="p-2 text-dark">Location</Link>
                            <Link to='/hashtag' className="p-2 text-dark">Hashtag</Link>
                        </nav>
                    </div>
                    <Route path='/location' component={Location} />
                    <Route path='/hashtag' component={Hashtag} />
                </div>
            </HashRouter>
        )
    }
}

export default Home;
