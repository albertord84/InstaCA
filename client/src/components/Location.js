import React, { Component } from 'react';
import { withRouter } from 'react-router';
import { Observable, fromEvent, range } from 'rxjs';
import { map, filter, debounceTime, distinctUntilChanged, scan } from 'rxjs/operators';
import axios from 'axios';

class Location extends Component {
    constructor(props) {
        super(props);
        this.state = {
            locations: [],
            searching: false,
            error: null,
            hasMore: false,
            count: 10,
            rankToken: ''
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
            NProgress.done();
        }, 1000);
    }
    handlePostQuerySuccess(response) {
        const currentList = this.state.locations;
        const data = response.data;
        const locations = data.ig.items.map(item => item.location);
        const newLocationsList = this.excludeFetched(currentList, locations);
        const diff = newLocationsList.length - currentList.length;
        toastr["success"](`Loaded ${diff} new geolocations`, "Geolocation search");
        setTimeout(() => {
            dumbu.cookies = data.cookies;
            this.setState({
                searching: false,
                locations: newLocationsList, // currentList.concat(locations),
                rankToken: data.ig.rank_token,
                hasMore: data.ig.has_more
            });
            NProgress.done();
        }, 1000);
    }
    validCredentials() {
        if (dumbu.username !== '' && dumbu.password !== '') {
            return true;
        }
        this.setState({ error: 'You must establish user credentials (username/password) first...' });
        return false;
    }
    excludeFetched(currentList, fetchedList) {
        if (currentList.length===0) return fetchedList;
        const currentListIds = currentList.map(location => location.facebook_places_id);
        const fetchedListIds = fetchedList.map(location => location.facebook_places_id);
        const trulyNew = fetchedListIds.filter(id => currentListIds.includes(id) === false);
        const newLocations = fetchedList.filter(location => trulyNew.includes(location.facebook_places_id));
        return currentList.concat(newLocations);
    }
    postInputQuery(query) {
        if (this.state.searching) return;
        if (!this.validCredentials()) return;
        NProgress.start();
        this.setState({ error: null, searching: true, locations: [], hasMore: false });
        const dumbu = window.dumbu;
        const pathName = window.location.pathname;
        const path = this.translateToServerSide(pathName);
        axios.post(path + '/location.php', {
            username: dumbu.username,
            password: dumbu.password,
            cookies: dumbu.cookies,
            count: this.state.count,
            location: query,
            exclude_list: this.excludeList(this.state.locations),
            rank_token: this.state.rankToken
        })
        .then(this.handlePostQuerySuccess)
        .catch(this.handlePostQueryError);
    }
    excludeList(locations) {
        let initial = '';
        const reducer = (acc, current, index, allItems) => {
            return acc += (index !== 0 ? ',' : '') + current.facebook_places_id;
        }
        return locations.reduce(reducer, initial);
    }
    searchMore() {
        NProgress.start();
        this.setState({ error: null, searching: true });
        const dumbu = window.dumbu;
        const pathName = window.location.pathname;
        const query = this.inputLocation.current.value;
        const path = this.translateToServerSide(pathName);
        axios.post(path + '/location.php', {
            username: dumbu.username,
            password: dumbu.password,
            cookies: dumbu.cookies,
            count: this.state.count,
            location: query,
            exclude_list: this.excludeList(this.state.locations),
            rank_token: this.state.rankToken
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
                { 
                    hasMore && error === null ?
                    <MoreButton searching={searching} searchMore={this.searchMore} />
                    : ''
                }
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
                <p className="mt-2 mb-2 small text-center"><small>Lat: {props.lat}</small></p>
                <p className="mt-0 small text-center"><small>Lng: {props.lng}</small></p>
            </div>
        </div>
    </div>
);

const locationsList = (locations) => {
    const list = locations.map(loc => {
        return <Item key={loc.facebook_places_id + _.uniqueId()}
            name={loc.name} lat={loc.lat} lng={loc.lng} />
    });
    return (
        <div className="d-flex justify-content-center mb-3">
            <div className="locations-list row justify-content-center">
                {list}
            </div>
        </div>
    );
};

const AlertError = (props) => (
    <div className="row d-flex justify-content-center">
        <div className="alert alert-danger mt-3 small">
            <strong>Error:</strong> {props.error}
        </div>
    </div>
);

export default withRouter(Location);
