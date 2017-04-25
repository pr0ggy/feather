<?php

return function ($testingResources) {
    ($testingResources['console'])->writeln('TEST FILE 1 INCLUDED');
    ($testingResources['validator'])->pass();
};
