import React from 'react';
import ReactDOM from 'react-dom';
import Home from './components/Home';

import './globals';
import './index.css';

ReactDOM.render(
    <Home startAt="location" />,
    document.getElementById('app')
);
