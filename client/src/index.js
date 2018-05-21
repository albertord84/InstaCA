import React from 'react';
import ReactDOM from 'react-dom';
import Home from './components/Home';
import { Redirect } from 'react-router-dom';

import './index.css';

ReactDOM.render(
    <Home />,
    document.getElementById('app')
);

