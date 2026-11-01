-- ==============================================================================================
-- ENTERPRISE DATA WAREHOUSE: 15 GUARANTEED BUSINESS QUERIES (ROUNDED DECIMALS)
-- ==============================================================================================
-- These queries rely ONLY on your core, fully-populated tables to guarantee results.
-- All floating-point calculations (AVG, SUM, MATH) are rounded to 2 decimal places for clean reporting.
-- ==============================================================================================

-- Query 1: Average Temperature by Province
-- Business Question: What is the average temperature recorded across each province?
-- Business Importance: Helps regional managers assess climatic comfort levels, impacting energy demand forecasting (like grid cooling load) across the country.
SELECT 
    g.province, 
    ROUND(AVG(f.temperature_c), 2) AS average_temperature
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_geography` g ON f.location_id = g.location_id 
GROUP BY g.province 
ORDER BY average_temperature DESC;

-- Query 2: Max Wind Speed by District
-- Business Question: What is the maximum wind speed recorded in each district?
-- Business Importance: Critical for issuing wind warnings, securing infrastructure, and planning activities sensitive to high winds like construction and shipping.
SELECT 
    g.district, 
    ROUND(MAX(f.wind_speed_kmh), 2) AS max_wind_speed
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_geography` g ON f.location_id = g.location_id 
GROUP BY g.district 
ORDER BY max_wind_speed DESC 
LIMIT 10;

-- Query 3: Total Precipitation by Climate Zone
-- Business Question: How much total precipitation has fallen in each distinct climate zone?
-- Business Importance: Helps understand broad weather patterns crucial for agriculture planning and national water resource management.
SELECT 
    g.climate_zone, 
    ROUND(SUM(f.precipitation_mm), 2) AS total_rainfall_mm
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_geography` g ON f.location_id = g.location_id 
GROUP BY g.climate_zone;

-- Query 4: Top 5 Hottest Cities
-- Business Question: Which 5 cities have recorded the highest peak temperatures?
-- Business Importance: Identifies areas experiencing severe heat, which is critical for issuing public safety warnings and protecting outdoor workers.
SELECT 
    g.city, 
    ROUND(MAX(f.temperature_c), 2) AS peak_temperature
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_geography` g ON f.location_id = g.location_id 
GROUP BY g.city 
ORDER BY peak_temperature DESC 
LIMIT 5;

-- Query 5: Average PM2.5 by River Basin
-- Business Question: What is the average fine particulate matter (PM2.5) pollution level grouped by river basin?
-- Business Importance: Provides insights into how pollution settles in different topographical basins, supporting targeted environmental interventions.
SELECT 
    h.river_basin, 
    ROUND(AVG(f.pm2_5), 2) AS average_pm25
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_topography_hazard` h ON f.location_id = h.location_id 
GROUP BY h.river_basin 
ORDER BY average_pm25 DESC;

-- Query 6: Weather Condition Breakdown
-- Business Question: What are the most frequently occurring weather conditions overall?
-- Business Importance: Establishes a baseline for normal weather operations and helps validate meteorological forecasting accuracy against actual recorded conditions.
SELECT 
    c.condition_name, 
    COUNT(f.fact_id) AS occurrence_count
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_condition` c ON f.weather_code = c.weather_code 
GROUP BY c.condition_name 
ORDER BY occurrence_count DESC;

-- Query 7: Average Pressure by Terrain Type
-- Business Question: How does atmospheric surface pressure vary based on the physical terrain (Highland vs Coastal)?
-- Business Importance: Aids meteorologists in understanding microclimates and tuning pressure-based altitude and weather prediction models for Sri Lankan topography.
SELECT 
    h.terrain_type, 
    ROUND(AVG(f.surface_pressure_hpa), 2) AS average_surface_pressure
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_topography_hazard` h ON f.location_id = h.location_id 
GROUP BY h.terrain_type
ORDER BY average_surface_pressure ASC;

-- Query 8: Average Humidity by Landslide Risk Level
-- Business Question: What is the average relative humidity in areas categorized by different landslide risk levels?
-- Business Importance: Crucial for proactive disaster preparedness. Tracking moisture in high-risk zones helps calibrate early warning evacuation systems.
SELECT 
    h.landslide_risk_level, 
    ROUND(AVG(f.relative_humidity_pct), 2) AS average_humidity
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_topography_hazard` h ON f.location_id = h.location_id 
GROUP BY h.landslide_risk_level;

-- Query 9: Apparent vs Actual Temp Difference by City
-- Business Question: Which cities have the highest average difference between actual temperature and "feels like" temperature?
-- Business Importance: Highlights locations with severe heat stress indices (often driven by high humidity), allowing the Ministry of Health to allocate resources.
SELECT 
    g.city, 
    ROUND(AVG(f.apparent_temp_c - f.temperature_c), 2) AS average_heat_index_difference
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_geography` g ON f.location_id = g.location_id 
GROUP BY g.city 
ORDER BY average_heat_index_difference DESC 
LIMIT 10;

-- Query 10: Air Quality Index Breakdown
-- Business Question: How many readings fall into each US EPA Air Quality Health Category?
-- Business Importance: Essential for high-level public health reporting, evaluating national pollution control measures, and communicating risks.
SELECT 
    a.health_category, 
    COUNT(f.fact_id) AS total_readings
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_air_quality_index` a ON f.us_aqi BETWEEN a.min_aqi AND a.max_aqi 
GROUP BY a.health_category
ORDER BY total_readings DESC;

-- Query 11: Temperature Volatility (Range) by Province
-- Business Question: Which provinces experience the widest swing between their maximum and minimum recorded temperatures?
-- Business Importance: Helps in agricultural planning (selecting crop resilience) and understanding infrastructure stress caused by thermal expansion and contraction.
SELECT 
    g.province, 
    ROUND((MAX(f.temperature_c) - MIN(f.temperature_c)), 2) AS temperature_volatility_range
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_geography` g ON f.location_id = g.location_id 
GROUP BY g.province 
ORDER BY temperature_volatility_range DESC;

-- Query 12: Average Wind Direction by Province
-- Business Question: What is the prevailing average wind direction recorded in each province?
-- Business Importance: Vital for aviation routing, maritime navigation, and optimal placement of wind energy turbines.
SELECT 
    g.province, 
    ROUND(AVG(f.wind_direction_deg), 2) AS average_wind_direction
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_geography` g ON f.location_id = g.location_id 
GROUP BY g.province;

-- Query 13: Precipitation by Flood Vulnerability
-- Business Question: How much total rainfall has accumulated in areas based on their specific flood vulnerability rating?
-- Business Importance: Directly correlates weather events to disaster risk, allowing emergency response teams to pre-position resources before severe flooding occurs.
SELECT 
    h.flood_vulnerability, 
    ROUND(SUM(f.precipitation_mm), 2) AS total_rainfall_mm
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_topography_hazard` h ON f.location_id = h.location_id 
GROUP BY h.flood_vulnerability
ORDER BY total_rainfall_mm DESC;

-- Query 14: Average PM10 and Nitrogen Dioxide by City
-- Business Question: What are the average PM10 and Nitrogen Dioxide pollution levels in each city?
-- Business Importance: Identifies urban centers with concerning levels of respiratory hazards, prompting air quality alerts and traffic/industrial regulations.
SELECT 
    g.city, 
    ROUND(AVG(f.pm10), 2) AS average_pm10, 
    ROUND(AVG(f.nitrogen_dioxide), 2) AS average_no2
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_geography` g ON f.location_id = g.location_id 
GROUP BY g.city 
ORDER BY average_pm10 DESC 
LIMIT 10;

-- Query 15: Most Common Weather Category by Province
-- Business Question: What weather condition category (e.g., Clear, Precipitation, Cloud) is most common in each province?
-- Business Importance: Provides a long-term climatic profile for different regions, aiding in tourism marketing and regional infrastructure design.
SELECT 
    g.province, 
    c.category, 
    COUNT(f.fact_id) AS condition_count
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_geography` g ON f.location_id = g.location_id 
JOIN `weather-warehouse.weather_data.dim_condition` c ON f.weather_code = c.weather_code 
GROUP BY g.province, c.category 
ORDER BY condition_count DESC 
LIMIT 15;
