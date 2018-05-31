import React, { Component } from 'react';
import { withRouter } from 'react-router';
import { Observable, fromEvent, range } from 'rxjs';

import { 
    map, filter, debounceTime,
    distinctUntilChanged, scan
} from 'rxjs/operators';

import axios from 'axios';

class Location extends Component {
    constructor(props) {
        super(props);
        this.state = {
            locations: [],
            searching: false,
            error: null
        };
        this.inputLocation = React.createRef();
        this.bindLocationInput = this.bindLocationInput.bind(this);
        this.postInputQuery = this.postInputQuery.bind(this);
        this.handlePostQueryError = this.handlePostQueryError.bind(this);
        this.handlePostQuerySuccess = this.handlePostQuerySuccess.bind(this);
        window._state = this.state;
    }
    componentDidMount() {
        this.bindLocationInput();
    }
    translateToServerSide(path, suffix = '/server') {
        let translatedPath;
        range(1, 2).pipe(
            scan((acc, p) => {
                return acc.substring(0, acc.lastIndexOf('/'));
            }, path)
        )
        .subscribe(p => {
            translatedPath = p + suffix;
        });
        return translatedPath;
    }
    handlePostQueryError(reason) {
        setTimeout(() => {
            this.setState({
                searching: false,
                locations: [],
                error: reason.message
            });
        }, 1000);
    }
    handlePostQuerySuccess(response) {
        setTimeout(() => {
            dumbu.cookies = response.data.cookies;
            this.setState({ searching: false, locations: response.data.ig.items });
        }, 1000);
    }
    validCredentials() {
        if (dumbu.username !== '' && dumbu.password !== '') {
            return true;
        }
        this.setState({ error: 'You must establish user credentials (username/password) first...' });
        return false;
    }
    postInputQuery(query) {
        this.setState({ error: null });
        if (!this.validCredentials()) return;
        const dumbu = window.dumbu;
        const pathName = window.location.pathname;
        const path = this.translateToServerSide(pathName);
        this.setState({ searching: true });
        axios.post(path + '/location.php', {
            username: dumbu.username,
            password: dumbu.password,
            cookies: dumbu.cookies,
            count: 10,
            location: query,
            exclude_list: [],
            rank_token: ''
        })
        .then(this.handlePostQuerySuccess)
        .catch(this.handlePostQueryError);
    }
    bindLocationInput() {
        fromEvent(this.inputLocation.current, 'keyup')
        .pipe(
            map(ev => ev.target.value),
            filter(v => _.trim(v)!==''),
            distinctUntilChanged(),
            debounceTime(700),
            map(v => _.trim(v))
        )
        .subscribe(this.postInputQuery);
    }
    render() {
        const locations = this.state.locations;
        const error = this.state.error;
        return (
            <div className="location-search">
                <div className="text-center text-muted mb-3">
                    <h1>Location Search Test</h1>
                </div>
                <div className="row justify-content-center">
                    <div className="col-8">
                        <form action="" method="POST" className="mr-5 ml-5">
                            <div className="form-group">
                                <input type="text" className="form-control form-control-lg"
                                    placeholder="Type to search Instagram location..."
                                    disabled={this.state.searching} ref={this.inputLocation}
                                    id="location" autoComplete="off" autoFocus="true" />
                            </div>
                        </form>
                    </div>
                </div>
                { 
                    error !== null ?
                        <div className="row d-flex justify-content-center">
                            <div className="alert alert-danger mt-3">
                                <strong>Error:</strong> {error}
                            </div>
                        </div>
                    : ''
                }
                {
                    locations.length > 0 ?
                        locations.map(item => <Item key={item.location.pk} data={item.location} />)
                    : ''
                }
            </div>
        )
    }
}

const Item = (location) => <div className="row mt-2">
    <div className="media">
        <div className="media-body">
            <h5 className="mt-0">{location.name}</h5>
        </div>
    </div>
</div>

export default withRouter(Location);
