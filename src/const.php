<?php

namespace Kase;

// TEST RUN MODES
const TEST_MODE_NORMAL = 1;     // test will run in sequence as normal
const TEST_MODE_ISOLATED = 2;   // test will run in isolation (no other tests in the suite will run)
const TEST_MODE_SKIPPED = 3;    // test will be skipped
