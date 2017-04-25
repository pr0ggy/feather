<?php

return function ($testingResources) {
    ($testingResources['console'])->writeln('TEST FILE 2 INCLUDED');
    ($testingResources['validator'])->pass();
};
