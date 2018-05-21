import React, { Component } from 'react';
import { withRouter } from 'react-router';

class Hashtag extends Component {
    render() {
        return (
            <div className="hashtag-search">
                <div className="text-center text-muted">
                    <h1>Hashtag Search Test</h1>
                </div>
            </div>
        )
    }
}

export default withRouter(Hashtag);
