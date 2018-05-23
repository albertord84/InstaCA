import React, { Component } from 'react';
import { withRouter } from 'react-router';
import { Observable, fromEvent } from 'rxjs';
import { map, filter, debounceTime, distinctUntilChanged } from 'rxjs/operators';
import { axios } from 'axios';

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
    }
    componentDidMount() {
        this.bindLocationInput();
    }
    bindLocationInput() {
        fromEvent(this.inputLocation.current, 'keyup')
        .pipe(
            filter(ev => ev.target.id === 'location'),
            map(ev => ev.target.value),
            distinctUntilChanged(),
            debounceTime(700),
            map(v => _.trim(v))
        )
        .subscribe(v => {
            // do something with the input...
            const dumbu = window.dumbu;
            const pathName = window.location.pathname;
            const path = pathName.substring(0, pathName.lastIndexOf("/"))
                + '/server/location.php';
            this.setState({ searching: true, error: null });
            axios.post(path, {
                username: dumbu.username,
                password: dumbu.password
            }).then(data => {
                setTimeout(() => {
                    this.setState({
                        searching: false,
                        locations: data
                    });
                }, 1000);
            }).catch(reason => {
                setTimeout(() => {
                    this.setState({ searching: false, locations: [] });
                    this.setState({ error: reason.message });
                }, 1000);
            })
        });
    }
    render() {
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
                                    id="location" autoComplete="false" autoFocus="true" />
                            </div>
                        </form>
                    </div>
                </div>
                { 
                    this.state.error !== null ?
                        <div class="alert alert-danger mt-3">
                            <strong>Error:</strong> {this.state.error}
                        </div>
                        : ''
                }
                <div className="row mt-3 justify-content-center text-muted small">
                    Results appear here...
                </div>
            </div>
        )
    }
}

export default withRouter(Location);
