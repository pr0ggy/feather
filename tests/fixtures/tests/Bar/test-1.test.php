<?php

return function ($testingResources) {
    ($testingResources['console'])->writeln('BAR TEST FILE 1 INCLUDED');
    // pass validation by not throwing any exceptions
};
