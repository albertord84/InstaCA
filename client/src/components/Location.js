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
            error: null,
            hasMore: false
        };
        this.inputLocation = React.createRef();
        this.bindLocationInput = this.bindLocationInput.bind(this);
        this.postInputQuery = this.postInputQuery.bind(this);
        this.handlePostQueryError = this.handlePostQueryError.bind(this);
        this.handlePostQuerySuccess = this.handlePostQuerySuccess.bind(this);
        this.searchMore = this.searchMore.bind(this);
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
        const data = response.data;
        const locations = data.ig.items
            .filter(item => !_.isUndefined(item.location))
            .map(item => item.location);
        setTimeout(() => {
            dumbu.cookies = data.cookies;
            this.setState({
                searching: false,
                locations: locations,
                hasMore: data.ig.has_more
            });
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
        if (this.state.searching) return;
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
    searchMore() {

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
        const hasMore = this.state.hasMore;
        const searching = this.state.searching;
        const redirectSubmit = (ev) => {
            ev.preventDefault();
            this.postInputQuery(this.inputLocation.current.value);
        }
        return (
            <div className="location-search">
                <div className="text-center text-muted mb-3">
                    <h1>Location Search Test</h1>
                </div>
                <div className="row justify-content-center">
                    <div className="col-8">
                        <form action="" method="POST" className="mr-5 ml-5"
                            onSubmit={redirectSubmit}>
                            <div className="form-group">
                                <input type="text" className="form-control form-control-lg"
                                    placeholder="Type to search Instagram location..."
                                    disabled={this.state.searching} ref={this.inputLocation}
                                    id="location" autoComplete="off" autoFocus="true" />
                            </div>
                        </form>
                    </div>
                </div>
                { error !== null ? <AlertError error={error} /> : '' }
                { locations.length > 0 ? locationsList(locations) : '' }
                { hasMore ? <MoreButton searching={searching} searchMore={this.searchMore} /> : '' }
            </div>
        )
    }
}

const MoreButton = (props) => (
    <div className="row justify-content-center mt-3 mb-3">
        <button className="btn btn-primary btn-sm"
            disabled={props.searching}
            onClick={props.searchMore}><small>More...</small></button>
    </div>
);

const Item = (props) => (
    <div className="location mt-2 mr-2 ml-2">
        <div className="border rounded p-3 box-shadow">
            <div className="">
                <p className="mt-0 text-muted small text-center">{props.name}</p>
            </div>
        </div>
    </div>
);

const locationsList = (locations) => {
    const list = locations.map(item => {
        return <Item key={item.key + _.uniqueId()}
            name={item.name} lat={item.lat} lng={item.lng} />
    });
    return (
        <div className="d-flex justify-content-center">
            <div className="locations-list row justify-content-center">
                {list}
            </div>
        </div>
    );
};

const AlertError = (props) => (
    <div className="row d-flex justify-content-center">
        <div className="alert alert-danger mt-3">
            <strong>Error:</strong> {props.error}
        </div>
    </div>
);

export default withRouter(Location);
