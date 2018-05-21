import React, { Component } from 'react';
import { withRouter } from 'react-router';
import { Observable, fromEvent } from 'rxjs';
import { map, filter, debounceTime, distinctUntilChanged } from 'rxjs/operators';

class Location extends Component {
    constructor(props) {
        super(props);
        this.state = {
            locations: [],
            searching: false
        };
        this.bindLocationInput = this.bindLocationInput.bind(this);
    }
    componentDidMount() {
        this.bindLocationInput();
    }
    bindLocationInput() {
        fromEvent(window, 'keyup')
        .pipe(
            filter(ev => ev.target.id === 'location'),
            map(ev => ev.target.value),
            distinctUntilChanged(),
            debounceTime(700),
            map(v => _.trim(v))
        )
        .subscribe(v => {
            // do something with the input...
            console.log(`"${v}"`);
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
                                    id="location" autoComplete="false" autoFocus="true" />
                            </div>
                        </form>
                    </div>
                </div>
                <div className="row mt-3 justify-content-center">
                    Test
                </div>
            </div>
        )
    }
}

export default withRouter(Location);
