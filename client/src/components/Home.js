import React, { Component } from 'react';
import { HashRouter, Route, Link, Redirect } from 'react-router-dom';
import { browserHistory } from 'react-router';

import * as _ from 'lodash';

import Location from './Location';
import Hashtag from './Hashtag';

class Home extends Component {
    constructor(props) {
        super(props);
        this.updateUserName = this.updateUserName.bind(this);
        this.updatePassword = this.updatePassword.bind(this);
    }
    componentDidMount() {
        window.location.href = '#/' + this.props.startAt;
    }
    updateUserName(ev) {
        let dumbu = window.dumbu;
        dumbu.username = ev.target.value;
    }
    updatePassword(ev) {
        let dumbu = window.dumbu;
        dumbu.password = ev.target.value;
    }
    render() {
        return (
            <HashRouter>
                <div>
                    <div className="d-flex flex-column flex-md-row align-items-center p-3 px-md-4 mb-3 bg-white border-bottom box-shadow">
                        <nav className="my-2 my-md-0 mr-md-3 w-100">
                            <Link to='/location' replace className="p-2 text-dark">Location</Link>
                            <Link to='/hashtag' replace className="p-2 text-dark">Hashtag</Link>
                            <div className="float-right dropdown dropleft">
                                <a className="p-2 text-muted btn btn-default btn-cred dropdown-toggle"
                                    data-toggle="dropdown">
                                    <i className="fa fa-archive"/>
                                </a>
                                <div className="dropdown-menu">
                                    <form action="" method="POST" className="m-4">
                                        <div className="form-group">
                                            <input type="text" className="form-control"
                                                placeholder="Instagram username"
                                                onInput={this.updateUserName} />
                                        </div>
                                        <div className="form-group">
                                            <input type="password" className="form-control"
                                                placeholder="Password..."
                                                onInput={this.updatePassword} />
                                        </div>
                                    </form>
                                </div>
                            </div>
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
