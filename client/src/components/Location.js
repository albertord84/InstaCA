import React, { Component } from 'react';
import { withRouter } from 'react-router';

class Location extends Component {
    render() {
        return (
            <div className="location-search">
                <div className="text-center text-muted">
                    <h1>Location Search Test</h1>
                </div>
            </div>
        )
    }
}

export default withRouter(Location);
