<?php

return function ($testingResources) {
    ($testingResources['console'])->writeln('BAR TEST FILE 1 INCLUDED');
    ($testingResources['validator'])->pass();
};
