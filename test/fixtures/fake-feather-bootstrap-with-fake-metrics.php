<?php

return function ($testResources) {
    $testResources['metricsLog']->recordedMetrics[] = 'foo';
    $testResources['metricsLog']->recordedMetrics[] = 'bar';
};
