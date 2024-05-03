const { Model, DataTypes } = require('sequelize')

const schema = {
	description: DataTypes.STRING,
	status: DataTypes.INTEGER
}

class DataRequest extends Model {
	static rawSchema = schema

	static associate(models) {
		this.hasOne(models.AuditorInvitation, { as: 'auditorInvitation', foreignKey: { name: 'dataRequestId', allowNull: true } })
		this.belongsTo(models.Entity, { as: 'fromEntity', foreignKey: { name: 'fromEntityId', allowNull: false } })
		this.belongsTo(models.Entity, { as: 'toEntity', foreignKey: { name: 'toEntityId', allowNull: true } })
	}

	static init(options) {
		super.init(schema, options)
	}

	// static init(options) {
	// super.init(schema, { ...options })
	// }
}

module.exports = DataRequest
