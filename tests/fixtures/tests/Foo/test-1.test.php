<?php

return function ($testingResources) {
    ($testingResources['console'])->writeln('FOO TEST FILE 1 INCLUDED');
    // pass validation by not throwing any exceptions
};
