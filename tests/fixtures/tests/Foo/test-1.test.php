<?php

return function ($testingResources) {
    ($testingResources['console'])->writeln('FOO TEST FILE 1 INCLUDED');
    ($testingResources['validator'])->pass();
};
